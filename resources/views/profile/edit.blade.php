<x-app-layout>
    <div class="pt-16 pb-16 bg-gray-100 min-h-screen overflow-x-hidden">
        <div class="max-w-sm mx-auto px-2 space-y-6">
            <div class="p-4 bg-white shadow rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 bg-white shadow rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
