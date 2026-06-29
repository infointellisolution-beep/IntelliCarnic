@extends('layouts.app')

@section('title', 'Nuevo artículo')

@section('content')
    @include('articulos._form', ['familias' => $familias, 'settings' => $settings, 'enctype' => 'multipart/form-data'])
@endsection