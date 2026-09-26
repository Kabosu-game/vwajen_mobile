@extends('errors.layout')
@section('code', '429')
@section('heading', __('Trop de requêtes'))
@section('message', $exception?->getMessage() && in_array(429, [403, 429, 503]) && ! str_contains($exception->getMessage(), 'SQLSTATE') ? $exception->getMessage() : __('Vous agissez un peu trop vite. Patientez quelques instants puis réessayez.'))
