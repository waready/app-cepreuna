<template>
    <div class="p-d-flex p-flex-column p-ai-center panel-login">
        <div v-if="canLogin" class="hidden fixed top-0 right-0 px-6 py-4 sm:block">
            <inertia-link v-if="$page.props.user" href="/dashboard" class="text-sm text-gray-700 underline"> Dashboard </inertia-link>

            <template v-else>
                <div class="p-grid">
                    <div class="p-col-12 p-mt-4">
                        <div class="p-text-center">
                            <inertia-link :href="route('login')" class="text-sm text-gray-700 underline p-mx-2 p-button p-button-warning"> Log in </inertia-link>

                            <inertia-link v-if="canRegister" :href="route('register')" class="ml-4 text-sm text-gray-700 underline p-mx-2 p-button p-button-secondary">
                                Register
                            </inertia-link>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        canLogin: Boolean,
        canRegister: Boolean,
        laravelVersion: String,
        phpVersion: String,
    },
};
</script>
