@extends('layouts.app')

@section('title', 'Editar artículo')

@section('content')
    @include('articulos._form', ['familias' => $familias, 'settings' => $settings])
@endsection