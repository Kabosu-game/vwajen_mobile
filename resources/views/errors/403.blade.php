@extends('errors.layout')
@section('code', '403')
@section('heading', __('Accès refusé'))
@section('message', $exception?->getMessage() && in_array(403, [403, 429, 503]) && ! str_contains($exception->getMessage(), 'SQLSTATE') ? $exception->getMessage() : __('Vous n\'avez pas l\'autorisation d\'accéder à cette page.'))
