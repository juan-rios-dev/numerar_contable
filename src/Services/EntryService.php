<?php

namespace Numerar\Contable\Services;

use Illuminate\Support\Facades\DB;
use Numerar\Contable\Enums\EntryStatus;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Exceptions\PeriodClosedException;
use Numerar\Contable\Exceptions\UnbalancedEntryException;
use Numerar\Contable\Models\AccountingEntry;
use Numerar\Contable\Models\AccountingEntryLine;
use Numerar\Contable\Models\AccountingEntrySequence;
use Numerar\Contable\Models\AccountingEntryType;
use Numerar\Contable\Models\AccountingPeriod;

class EntryService
{
    // ── Crear comprobante (directo como POSTED) ───────────────

    public function create(array $data): AccountingEntry
    {
        return DB::transaction(function () use ($data) {
            $typeCode = $data['entry_type'];
            $date     = $data['date'];
            $year     = (int) date('Y', strtotime($date));
            $month    = (int) date('n', strtotime($date));

            $period   = $this->resolveOpenPeriod($year, $month);

            if (! $period->containsDate($date)) {
                throw AccountingException::make(
                    "La fecha {$date} no pertenece al periodo {$period->name}."
                );
            }

            $sequence = $this->resolveSequence($typeCode, $data['entry_sequence_id'] ?? null);

            $entry = AccountingEntry::create([
                'accounting_period_id' => $period->id,
                'entry_number'         => $sequence->formatNumber($year),
                'entry_type'           => $typeCode,
                'entry_sequence_id'    => $sequence->id,
                'date'                 => $date,
                'description'          => $data['description'] ?? null,
                'status'               => EntryStatus::POSTED->value,
                'created_by'           => $data['created_by'] ?? null,
            ]);

            $this->syncLines($entry, $data['lines'] ?? []);

            $entry->load('lines.account');
            $this->validateEntry($entry);

            return $entry->load('lines');
        });
    }

    // ── Crear comprobante en período específico (cierre de ejercicio) ─

    public function createInPeriod(AccountingPeriod $period, array $data): AccountingEntry
    {
        return DB::transaction(function () use ($period, $data) {
            $date     = $data['date'];
            $year     = (int) date('Y', strtotime($date));
            $sequence = $this->resolveSequence($data['entry_type'], $data['entry_sequence_id'] ?? null);

            $entry = AccountingEntry::create([
                'accounting_period_id' => $period->id,
                'entry_number'         => $sequence->formatNumber($year),
                'entry_type'           => $data['entry_type'],
                'entry_sequence_id'    => $sequence->id,
                'date'                 => $date,
                'description'          => $data['description'] ?? null,
                'status'               => EntryStatus::POSTED->value,
                'created_by'           => $data['created_by'] ?? null,
            ]);

            $this->syncLines($entry, $data['lines'] ?? []);
            $entry->load('lines.account');
            $this->validateEntry($entry);

            return $entry->load('lines');
        });
    }

    // ── Editar comprobante ────────────────────────────────────

    public function update(AccountingEntry $entry, array $data): AccountingEntry
    {
        if ($entry->isVoided()) {
            throw AccountingException::make(
                "El comprobante '{$entry->entry_number}' está anulado y no puede editarse."
            );
        }

        return DB::transaction(function () use ($entry, $data) {
            $date  = $data['date'] ?? $entry->date->toDateString();
            $year  = (int) date('Y', strtotime($date));
            $month = (int) date('n', strtotime($date));

            $period = $this->resolveOpenPeriod($year, $month);

            if (! $period->containsDate($date)) {
                throw AccountingException::make(
                    "La fecha {$date} no pertenece al periodo {$period->name}."
                );
            }

            $updates = ['accounting_period_id' => $period->id];

            if (isset($data['date'])) {
                $updates['date'] = $data['date'];
            }
            if (array_key_exists('description', $data)) {
                $updates['description'] = $data['description'];
            }

            $entry->update($updates);

            if (isset($data['lines'])) {
                $this->syncLines($entry, $data['lines']);
            }

            $entry->load('lines.account');
            $this->validateEntry($entry);

            return $entry->fresh('lines');
        });
    }

    // ── Anular comprobante ────────────────────────────────────

    public function void(AccountingEntry $entry): AccountingEntry
    {
        if ($entry->isVoided()) {
            throw AccountingException::make(
                "El comprobante '{$entry->entry_number}' ya está anulado."
            );
        }

        $entry->update(['status' => EntryStatus::VOIDED->value]);

        return $entry->fresh();
    }

    // ── Eliminar comprobante ──────────────────────────────────

    public function delete(AccountingEntry $entry): bool
    {
        return DB::transaction(function () use ($entry) {
            $entry->lines()->delete();
            return $entry->delete();
        });
    }

    // ── Numeración ────────────────────────────────────────────

    private function resolveSequence(string $typeCode, ?int $sequenceId): AccountingEntrySequence
    {
        if ($sequenceId) {
            $sequence = AccountingEntrySequence::find($sequenceId);

            if (! $sequence || $sequence->entryType->code !== $typeCode) {
                throw AccountingException::make(
                    "La numeración seleccionada no corresponde al tipo de comprobante '{$typeCode}'."
                );
            }

            return $sequence;
        }

        $type = AccountingEntryType::where('code', $typeCode)->first();

        if (! $type) {
            throw AccountingException::make(
                "El tipo de comprobante '{$typeCode}' no existe."
            );
        }

        $sequence = $type->defaultSequence();

        if (! $sequence) {
            throw AccountingException::make(
                "El tipo de comprobante '{$type->name}' no tiene ninguna numeración activa configurada."
            );
        }

        return $sequence;
    }

    // ── Helpers privados ──────────────────────────────────────

    private function resolveOpenPeriod(int $year, int $month): AccountingPeriod
    {
        $period = AccountingPeriod::where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $period) {
            throw AccountingException::make(
                "No existe un período contable para {$month}/{$year}. " .
                "Abra el período correspondiente antes de registrar comprobantes."
            );
        }

        if ($period->isClosed()) {
            throw PeriodClosedException::forPeriod($period->name);
        }

        return $period;
    }

    private function validateEntry(AccountingEntry $entry): void
    {
        $lines = $entry->lines;

        if ($lines->count() < 2) {
            throw AccountingException::make(
                "El comprobante debe tener al menos 2 líneas. Tiene {$lines->count()}."
            );
        }

        foreach ($lines as $line) {
            $this->validateLine($line);
        }

        if (! $entry->isBalanced()) {
            throw UnbalancedEntryException::withDifference($entry->difference());
        }
    }

    private function validateLine(AccountingEntryLine $line): void
    {
        $debit  = (float) $line->debit;
        $credit = (float) $line->credit;

        if ($debit > 0 && $credit > 0) {
            throw AccountingException::make(
                "Línea inválida: no se puede registrar débito y crédito simultáneamente en la misma línea."
            );
        }

        if ($debit <= 0 && $credit <= 0) {
            throw AccountingException::make(
                "Línea inválida: debe registrar un débito o crédito mayor a cero."
            );
        }

        $account = $line->account;

        if (! $account) {
            throw AccountingException::make(
                "Línea inválida: la cuenta con id {$line->account_id} no existe."
            );
        }

        if (! $account->active) {
            throw AccountingException::make(
                "La cuenta [{$account->code}] {$account->name} está inactiva."
            );
        }
    }

    private function syncLines(AccountingEntry $entry, array $lines): void
    {
        $entry->lines()->delete();

        $records = array_map(fn (array $line) => [
            'entry_id'          => $entry->id,
            'account_id'        => $line['account_id'],
            'description'       => $line['description'] ?? null,
            'debit'             => $line['debit'] ?? 0,
            'credit'            => $line['credit'] ?? 0,
            'third_party_id'    => $line['third_party_id'] ?? null,
            'third_party_type'  => $line['third_party_type'] ?? null,
            'cost_center_id'    => $line['cost_center_id'] ?? null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $lines);

        AccountingEntryLine::insert($records);
    }
}
