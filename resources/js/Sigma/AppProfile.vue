<template>
    <div class="layout-profile sidebar-header-mobile">
        <div class="prueba">
            <!-- <img :src="$page.props.user.profile_photo_url" alt="" /> -->
            <Avatar
                v-if="!$page.props.usuario.profile_photo_path"
                :image="$page.props.usuario.profile_photo_url"
                class="mr-2 mt-5 mb-1"
                size="xlarge"
                shape="circle"
                style="width: 6rem; height: 6rem; background-color: #ff9c56"
            />
            <Avatar v-else :image="$page.props.usuario.profile_photo_path" class="mr-2 mt-5 mb-1" size="xlarge" shape="circle" style="width: 6rem; height: 6rem; background-color: #ff9c56" />
        </div>
        <button class="p-link text-white layout-profile-link mb-3" @click="onClick">
            <span class="username">{{ $page.props.usuario.nombres }}</span>
            <i class="pi pi-fw pi-cog"></i>
        </button>
        <transition name="layout-submenu-wrapper">
            <ul v-show="expanded">
                <li>
                    <inertia-link :href="route('perfil')"
                        ><button class="p-link"><i class="pi pi-fw pi-user"></i><span>Mis Datos</span></button></inertia-link
                    >

                    <!-- <button class="p-link"><i class="pi pi-fw pi-user"></i><span>Mi Perfil</span></button> -->
                </li>
                <!-- <li>
                    <button class="p-link"><i class="pi pi-fw pi-inbox"></i><span>Notificaciones</span><span class="menuitem-badge">2</span></button>
                </li> -->
                <li>
                    <button class="p-link" @click.prevent="logout"><i class="pi pi-fw pi-power-off"></i><span>Salir</span></button>
                </li>
            </ul>
        </transition>
    </div>
</template>

<script>
export default {
    props: {
        user: Object,
    },
    data() {
        return {
            expanded: false,
        };
    },
    methods: {
        onClick(event) {
            this.expanded = !this.expanded;
            event.preventDefault();
        },
        logout() {
            this.$inertia.post(route("logout"));
        },
    },
};
</script>

<style scoped></style>
