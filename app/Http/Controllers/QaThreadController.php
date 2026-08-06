<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\QaThread\IndexRequest;
use Illuminate\Http\Request;

/**
 * QaBoard Controller。受講生 / コーチ / admin 共通で利用される。
 *
 * - index:
 * - show:
 * - create:
 * - store:
 * - edit:
 * - update:
 * - destroy
 */
class QaThreadController extends Controller
{
    public function index(IndexRequest $request)
    {
        $filters = $request->filters();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
