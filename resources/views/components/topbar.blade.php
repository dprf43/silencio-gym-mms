<aside class="h-20 flex justify-between items-center px-6 py-4 bg-white border-b border-gray-200">
    <div class="flex items-center gap-4">
        <!-- Mobile Toggle Button -->
        <button class="sidebar-toggle md:hidden cursor-pointer p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <img src="{{ asset('images/icons/dashboard-icon.svg') }}" alt="" class="w-8 h-8 invert">
        <h1 class="text-xl font-semibold text-gray-900">{{ $slot }}</h1>
    </div>
    <div class="flex items-center gap-4">
        <div class="relative">
            <div class="flex items-center bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-100 transition-colors duration-200 focus-within:bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-200">
                <img src="{{ asset('images/icons/search-icon.svg') }}" alt="Search Icon" class="w-5 h-5 invert opacity-60 mr-3">
                <input type="text" placeholder="Search members..." class="bg-transparent outline-none text-gray-700 placeholder-gray-500 flex-1 min-w-48">
            </div>
        </div>
        <button class="cursor-pointer relative p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
            <img src="{{ asset('images/icons/phone-icon.svg') }}" alt="Phone Icon" class="w-8 h-8 invert">
            <div class="bg-orange-500 rounded-full w-5 h-5 flex justify-center items-center absolute -top-1 -right-1">
                <p class="text-white text-xs font-bold">0</p>
            </div>
        </button>
        <button class="cursor-pointer relative p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
            <img src="{{ asset('images/icons/alert-icon.svg') }}" alt="Alert Icon" class="w-8 h-8 invert">
            <div class="bg-orange-500 rounded-full w-5 h-5 flex justify-center items-center absolute -top-1 -right-1">
                <p class="text-white text-xs font-bold">0</p>
            </div>
        </button>
        <div class="relative">
            <button id="profile-dropdown-toggle" class="cursor-pointer p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                <img src="{{ asset('images/icons/profile-account-icon.svg') }}" alt="Profile Account Icon" class="w-8 h-8 invert">
            </button>
            <!-- Profile Dropdown Menu -->
            <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible transition-all duration-200 transform scale-95 origin-top-right z-50">
                <div class="py-1">
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200 flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileDropdownToggle = document.getElementById('profile-dropdown-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');
    let isDropdownOpen = false;

    // Toggle dropdown
    profileDropdownToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        isDropdownOpen = !isDropdownOpen;
        
        if (isDropdownOpen) {
            profileDropdown.classList.remove('opacity-0', 'invisible', 'scale-95');
            profileDropdown.classList.add('opacity-100', 'visible', 'scale-100');
        } else {
            profileDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
            profileDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!profileDropdownToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
            isDropdownOpen = false;
            profileDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
            profileDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
        }
    });

    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isDropdownOpen) {
            isDropdownOpen = false;
            profileDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
            profileDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
        }
    });
});
</script>