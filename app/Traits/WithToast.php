<?php

namespace App\Traits;

use Flux\Flux;

trait WithToast
{
    protected function showSuccess(string $message, string $heading = 'Success!'): void
    {
        Flux::toast(
            text: $message,
            heading: $heading,
            variant: 'success'
        );
    }

    protected function showError(string $message, string $heading = 'Error'): void
    {
        Flux::toast(
            text: $message,
            heading: $heading,
            variant: 'danger'
        );
    }

    protected function showWarning(string $message, string $heading = 'Warning'): void
    {
        Flux::toast(
            text: $message,
            heading: $heading,
            variant: 'warning'
        );
    }

    protected function showInfo(string $message, string $heading = 'Info'): void
    {
        Flux::toast(
            text: $message,
            heading: $heading,
            variant: 'info'
        );
    }
}
