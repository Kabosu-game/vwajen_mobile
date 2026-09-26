@extends('errors.layout')
@section('code', '404')
@section('heading', __('Page introuvable'))
@section('message', $exception?->getMessage() && in_array(404, [403, 429, 503]) && ! str_contains($exception->getMessage(), 'SQLSTATE') ? $exception->getMessage() : __('Ce contenu n\'existe pas, a été supprimé ou n\'est pas disponible.'))
