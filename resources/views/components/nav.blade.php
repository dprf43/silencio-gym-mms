<aside class="sidebar bg-gray-900 transition-all duration-300 ease-in-out collapsed" id="sidebar">
    <div class="flex flex-col items-center h-full gap-5 p-5">
        <!-- Logo/Brand -->
        <div class="sidebar-logo flex items-center justify-center w-full">
            <img src="{{ asset('images/icons/placeholder-icon.svg') }}" alt="" class="w-12 h-12 object-cover transition-all duration-300">
        </div>
        
        <hr class="border border-gray-700 w-full">
        
        <!-- Navigation -->
        <nav class="w-full flex flex-col gap-3">
            <x-nav-item src="images/icons/dashboard-icon.svg" href="/dashboard" dataTitle="Dashboard">Dashboard</x-nav-item>
            
            <x-nav-item src="images/icons/members-icon.svg" dropdown="true" dataTitle="Members">
                Members
                <x-slot name="dropdownContent">
                    <x-nav-sub-item src="images/icons/members-icon.svg" href="/members">Members</x-nav-sub-item>
                    <x-nav-sub-item src="images/icons/members-icon.svg" href="/membership/plans">Membership Plans</x-nav-sub-item>
                </x-slot>
            </x-nav-item>
            
            <x-nav-item src="images/icons/payments-icon.svg" href="/membership/payments" dataTitle="All Payments">All Payments</x-nav-item>
            
            <x-nav-item src="images/icons/search-icon.svg" href="/membership/manage-member" dataTitle="Member Plan Management">Member Plans</x-nav-item>
            
            <x-nav-item src="images/icons/search-icon.svg" href="/rfid-monitor" dataTitle="RFID Monitor">RFID Monitor</x-nav-item>
        </nav>
    </div>
</aside>