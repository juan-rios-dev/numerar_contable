<?php

if (! function_exists('accounting_model')) {
    /**
     * Retorna la clase del modelo configurado para la clave dada.
     * Permite que el consuming app intercambie cualquier modelo del paquete.
     *
     * Uso:
     *   accounting_model('account')::find($id)
     *   accounting_model('entry')::with('lines')->get()
     */
    function accounting_model(string $key): string
    {
        return config("contable.models.{$key}");
    }
}
