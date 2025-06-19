<div
    x-data="{
        canGoBack: history.length > 1,          // true only if there *is* a previous entry
        goBack() {                              // reusable helper
            if (this.canGoBack) {
                history.back()
            } else {
                // optional fallback – close the tab OR send them home
                window.location.href = '{{ route('task-board') }}'
            }
        }
    }"
    x-cloak                                     
>
    <flux:button
        x-show="canGoBack"
        icon="arrow-left"
        variant="ghost"
        x-on:click.prevent="goBack"
    >
        Back
    </flux:button>
</div>
