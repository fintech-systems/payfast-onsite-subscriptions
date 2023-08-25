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
            Go to billing<span aria-hidden="true"> &rarr;</span>
          </a>
        </span>
      </p>
    </div>
  </div>
</div>
