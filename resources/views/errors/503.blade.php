@extends('errors.layout')
@section('code', '503')
@section('heading', __('Maintenance en cours'))
@section('message', $exception?->getMessage() && in_array(503, [403, 429, 503]) && ! str_contains($exception->getMessage(), 'SQLSTATE') ? $exception->getMessage() : __('Vwajèn est en cours de maintenance. Revenez dans quelques minutes.'))
