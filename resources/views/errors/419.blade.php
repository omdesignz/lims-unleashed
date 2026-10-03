@extends('errors::minimal')

@section('title', 'Sessão expirada')
@section('code', '419')
@section('message', 'A página expirou por inactividade.')
@section('description', 'Por segurança, os formulários deixam de ser válidos ao fim de algum tempo. Volte à página anterior, actualize-a e repita a operação.')
