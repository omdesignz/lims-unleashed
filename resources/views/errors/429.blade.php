@extends('errors::minimal')

@section('title', 'Demasiados pedidos')
@section('code', '429')
@section('message', 'Foram feitos demasiados pedidos em pouco tempo.')
@section('description', 'Aguarde alguns instantes antes de tentar novamente. Nenhum dado foi alterado.')
