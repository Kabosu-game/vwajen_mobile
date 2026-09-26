@extends('errors.layout')
@section('code', '419')
@section('heading', __('Session expirée'))
@section('message', $exception?->getMessage() && in_array(419, [403, 429, 503]) && ! str_contains($exception->getMessage(), 'SQLSTATE') ? $exception->getMessage() : __('Votre session a expiré. Rechargez la page et réessayez.'))
