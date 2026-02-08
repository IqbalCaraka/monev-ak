<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use Illuminate\Http\Request;

class InstansiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $instansi = Instansi::orderBy('nama')->paginate(20);
        return view('pengaturan.instansi.index', compact('instansi'));
    }
}
