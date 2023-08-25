<div class="bg-white">

  <div x-data='{ monthly: 1 }' class="max-w-7xl mx-auto pt-12 pb-24 px-4 sm:px-6 lg:px-8">

    <a name='pricing'></a>

    <div class="sm:flex sm:flex-col sm:align-center">
      <h1 class="text-5xl font-extrabold text-gray-900 sm:text-center"><span class="text-indigo-700">Pricing </span>Plans
      </h1>
      <p class="mt-5 text-xl text-gray-500 sm:text-center">From solo to enterprise, we've got you covered.</p>
      <div class="relative self-center mt-6 bg-gray-100 rounded-lg p-0.5 flex sm:mt-8">
        <button x-on:click="monthly = 1" type="button"
          class="relative w-1/2 bg-white border-gray-200 rounded-md shadow-sm py-2 text-sm font-medium text-gray-900 whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:z-10 sm:w-auto sm:px-8">
          Monthly billing
        </button>
        <button x-on:click="monthly = 0" type="button"
          class="ml-0.5 relative w-1/2 border border-transparent rounded-md py-2 text-sm font-medium text-gray-700 whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:z-10 sm:w-auto sm:px-8">
          Yearly billing</button>
      </div>
    </div>

    <div
      class="mt-12 space-y-4 sm:mt-16 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-6 lg:max-w-4xl lg:mx-auto xl:max-w-none xl:mx-0 xl:grid-cols-3">

      @foreach ($pricingTiers as $tier)
        <div class="border border-gray-200 hover:border-indigo-700 rounded-lg shadow-sm divide-y divide-gray-200">
          <div class="p-6">
            <h2 class="text-lg leading-6 font-medium text-gray-900">{{ $tier['title'] }}</h2>
            <p class="mt-4 text-sm text-gray-500">{{ $tier['description'] }}</p>
            <p class="mt-8">
              <span class="text-4xl font-extrabold text-indigo-700">
                <span x-html='monthly ? "${{ $tier['monthly'] }}" : "${{ $tier['yearly'] }}"'>
                </span>
                <span class="text-base font-medium text-gray-500">
                  <span x-html='monthly ? "/month" : "/year"'></span>
                </span>
            </p>
            <a href="/register"
              class="mt-8 block w-full bg-gray-800 border border-gray-800 rounded-md py-2 text-sm font-semibold text-white text-center hover:bg-indigo-700">START
              FREE TRIAL</a>
          </div>

          <!-- Features -->
          <div class="pt-6 pb-8 px-6">
            <h3 class="text-xs font-medium text-gray-900 tracking-wide uppercase">What's included</h3>
            <ul role="list" class="mt-6 space-y-4">
              @foreach ($tier['features'] as $feature)
                <li class="flex space-x-3">
                  <!-- Heroicon name: solid/check -->
                  <svg class="flex-shrink-0 h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clip-rule="evenodd" />
                  </svg>
                  <span class="text-sm text-gray-500">{{ $feature }}</span>
                </li>
              @endforeach
            </ul>
          </div>

        </div>
      @endforeach

    </div>
  </div>
</div>
