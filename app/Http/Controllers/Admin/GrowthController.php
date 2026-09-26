<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Newsletter et programme ambassadeurs. */
class GrowthController extends Controller
{
    public function newsletter()
    {
        return view('admin.newsletter', [
            'subscribers' => NewsletterSubscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
            'pending' => NewsletterSubscriber::whereNull('confirmed_at')->count(),
            'newsletters' => Newsletter::with('user')->latest()->paginate(20),
        ]);
    }

    public function sendNewsletter(Request $request)
    {
        $data = $request->validate(['subject' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'max:20000']]);
        $newsletter = Newsletter::create($data + ['user_id' => $request->user()->id]);
        $count = 0;
        NewsletterSubscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->chunkById(200, function ($subs) use ($data, &$count) {
            foreach ($subs as $s) {
                $body = $data['body']."\n\n—\n".__('Se désinscrire').' : '.route('newsletter.unsubscribe', $s->token);
                Mail::raw($body, fn ($m) => $m->to($s->email)->subject($data['subject']));
                $count++;
            }
        });
        $newsletter->update(['sent_at' => now(), 'recipients_count' => $count]);
        AuditLogger::log('newsletter.send', $newsletter, ['recipients' => $count]);

        return back()->with('status', __('Newsletter envoyée à :n abonnés.', ['n' => $count]));
    }

    public function ambassadors(Request $request)
    {
        $ambassadors = Ambassador::with('user')->when($request->query('status', 'pending'), fn ($q, $s) => $s === 'all' ? $q : $q->where('status', $s))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.ambassadors', compact('ambassadors'));
    }

    public function decideAmbassador(Request $request, Ambassador $ambassador, Notifier $notifier)
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        $ambassador->update($data);
        AuditLogger::log('ambassador.'.$data['status'], $ambassador->user);
        $notifier->send($ambassador->user, 'system', null, $data['status'] === 'approved'
            ? 'Félicitations ! Vous êtes ambassadeur Vwajèn. Votre code : :code' : 'Votre candidature ambassadeur n\'a pas été retenue.',
            ['code' => $ambassador->referral_code], route('ambassadors.index'));

        return back()->with('status', __('Décision enregistrée.'));
    }
}
