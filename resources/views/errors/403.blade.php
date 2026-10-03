@extends('errors::minimal')

@section('title', 'Sem permissão')
@section('code', '403')
@section('message', 'Não tem permissão para ver esta página.')
@section('description', 'O seu perfil neste laboratório não inclui este módulo. Peça acesso ao administrador ou mude para um laboratório onde tenha essa permissão.')
