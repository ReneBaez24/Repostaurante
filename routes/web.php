<?php

use Illuminate\Support\Facades\Route;

// Vista Principal (Mesas)
Route::get('/', function () {
    return view('overview');
})->name('overview');

// Vista de Pedidos
Route::get('/pedidos', function () {
    return view('order');
})->name('order');

// Vista de Cobro
Route::get('/cobro', function () {
    return view('checkout');
})->name('checkout');

// Vista de Login
Route::get('/login', function () {
    return view('login');
})->name('login');

// Vista de Administrador (Roles)
Route::get('/admin', function () {
    return view('admin');
})->name('admin');