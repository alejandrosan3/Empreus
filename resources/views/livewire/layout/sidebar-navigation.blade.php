<flux:navlist variant="outline">
    <flux:navlist.item icon="home" href="/dashboard" :current="request()->is('dashboard')">Dashboard</flux:navlist.item>
    <flux:navlist.item icon="building-office" href="/builders" :current="request()->is('builders')">Builders</flux:navlist.item>
</flux:navlist>