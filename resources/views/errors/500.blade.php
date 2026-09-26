@extends('errors.layout')
@section('code', '500')
@section('heading', __('Erreur du serveur'))
@section('message', $exception?->getMessage() && in_array(500, [403, 429, 503]) && ! str_contains($exception->getMessage(), 'SQLSTATE') ? $exception->getMessage() : __('Un problème est survenu de notre côté. Nous y travaillons.'))
