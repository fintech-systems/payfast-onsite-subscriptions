<div class="relative bg-indigo-600">
  <div class="mx-auto max-w-7xl py-3 px-3 sm:px-6 lg:px-8">
    <div class="pr-16 sm:px-16 sm:text-center">
      <p class="font-medium text-white">
        <!-- Responsive message -->
        <span class="md:hidden">{{ $message }}</span>
        <!-- Normal message -->
        <span class="hidden md:inline">{{ $message }}</span>
        <span class="block sm:ml-2 sm:inline-block">
          <a href="/user/billing" class="font-bold text-white underline">
            Go to billing
            <span aria-hidden="true"> &rarr;</span>
          </a>
        </span>
      </p>
    </div>
    {{-- <div class="absolute inset-y-0 right-0 flex items-start pt-1 pr-1 sm:items-start sm:pt-1 sm:pr-2">
      <button 
        wire:click="hide"
        type="button"        
        class="flex rounded-md p-2 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-white">
        <span class="sr-only">Dismiss</span>
        <!-- Heroicon name: outline/x-mark -->
        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div> --}}
  </div>
</div>