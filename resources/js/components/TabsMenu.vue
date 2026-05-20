<template>
    <div>
        <ul class="tabs__header">
            <li class="flex justify-content-between" v-for="(tab, i) in tabs" :key="tab.title" @click="selectedTitle = tab.title;refreshNotificacion(i);" :class="{ selected: tab.title == selectedTitle }">
                <i :class="'pi ' + tab.icon">
                    <Badge value="2" class="mr-2 alerta" v-if="nofitication.alert && nofitication.index == i"> {{ nofitication.count }}</Badge>
                </i>
                <span class="ml-2" v-if="showTitle">{{ tab.title }}</span>
            </li>
        </ul>
        <slot />
    </div>
</template>

<script>
import { onMounted, provide, ref, toRefs } from "vue";
export default {
    props: {
        // notificacion: Array,
    },
    setup(props, { slots, attrs }) {
        // const tabTitles = ref("");
        // const { notificacion} = toRefs(props);
        const nofitication = ref({
            alert: false,
            count: 0,
            index: 0,
        });
        const tabs = ref(slots.default().map((tab) => tab.props));
        const selectedTitle = ref(tabs.value[0].title);
        const isMobile = () => {
            return window.innerWidth < 1024;
        };
        const showTitle = ref(false);
        const refreshNotificacion = (index) => {
            // console.log(index)
            if(index==4){
                getAlertNotificaciones();
            }
        }
        const getAlertNotificaciones = () => {
            // console.log(item.value);
            axios.get(route("recursos.alert-notificaciones"), {}).then((response) => {
                nofitication.value.alert = response.data.status;
                nofitication.value.count = response.data.count;
                nofitication.value.index = 4;
            });
        };
        onMounted(() => {
            const tabs = ref(slots.default().map((tab) => tab.props));
            console.log(tabs);
            if (isMobile()) {
                showTitle.value = false;
            } else {
                showTitle.value = true;
            }
            // console.log(route().current());
            // console.log(route())
        });
        provide("selectedTitle", selectedTitle);
        // provide("alertItem", alertItem);
        return {
            nofitication,
            tabs,
            selectedTitle,
            showTitle,
            refreshNotificacion
        };
    },
};
</script>

<style lang="scss" scoped>
.tabs {
    margin: 0 auto;
}
.tabs__header {
    margin-bottom: 10px;
    list-style: none;
    padding: 0;
    display: flex;
    margin-top: 0px;
}
.tabs__header li {
    color: #a7a7a7;
    text-align: center;
    padding: 10px 20px;
    border-bottom: 3px #ddd solid;
    cursor: pointer;
    transition: 0.4s all ease-out;
    flex: auto;
    justify-content: center !important;
}
.tabs__header li i {
    font-size: 20px;
    position: relative;
}
.tabs__header li i .alerta {
    position: absolute;
    bottom: 10px;
    left: 8px;
}
.tabs__header li.selected {
    color: #ff9c56;
    border-bottom: 3px #ff9c56 solid;
}
.tabs__header li.selected i {
    font-size: 20px;
    // font-weight: bold;
}
.tabs__header li.selected span {
    font-size: 15px;
    font-weight: bold;
}
// .tabs__header li i .p-badge{
//     min-width: 0.5rem !important;
//     height: 0.5rem !important;
//     background: blue;
// }
</style>
