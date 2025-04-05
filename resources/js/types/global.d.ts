import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import {route as ziggyRoute, Config as ZiggyConfig } from 'ziggy-js';
import { PageProps as AppPageProps } from './';
import { Alpine } from 'alpinejs';

declare global {
    interface Window {
        axios: AxiosInstance;
        route: ziggyRoute;
        Alpine: Alpine
    }

    var route: typeof ziggyRoute;
    var $page: any
    var Ziggy: ZiggyConfig;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
        $page: any
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps { }
}
