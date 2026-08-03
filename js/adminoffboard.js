(function() {
    'use strict';

    if (typeof Vue === 'undefined' || typeof VueRouter === 'undefined') {
        console.error('Vue or VueRouter not loaded');
        return;
    }

    Vue.use(VueRouter);

    var routes = [
        { path: '/', redirect: '/dashboard' },
        { path: '/dashboard', component: { template: '<div><h2>Dashboard</h2><p>Welcome to Admin Offboard</p></div>' }, name: 'dashboard' },
        { path: '/offboard', component: { template: '<div><h2>Offboard Users</h2><p>Offboard users component</p></div>' }, name: 'offboard' },
        { path: '/bulk', component: { template: '<div><h2>Bulk Operations</h2><p>Bulk operations component</p></div>' }, name: 'bulk' },
        { path: '/audit', component: { template: '<div><h2>Audit Logs</h2><p>Audit logs component</p></div>' }, name: 'audit' },
        { path: '/queue', component: { template: '<div><h2>Queue Management</h2><p>Queue management component</p></div>' }, name: 'queue' },
        { path: '/settings', component: { template: '<div><h2>Settings</h2><p>Settings component</p></div>' }, name: 'settings' }
    ];

    var router = new VueRouter({ routes: routes, mode: 'hash' });

    function updateNav(name) {
        var items = document.querySelectorAll('.nav-item');
        for (var i = 0; i < items.length; i++) {
            items[i].classList.toggle('active', items[i].dataset.view === name);
        }
    }

    function bindNavLinks() {
        var links = document.querySelectorAll('.nav-item a');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function(e) {
                e.preventDefault();
                var path = this.getAttribute('href').replace('#', '');
                router.push({ path: path }).catch(function() {});
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var appContent = document.getElementById('app-content');
        if (!appContent) {
            console.error('app-content not found');
            return;
        }

        var mountPoint = document.createElement('div');
        appContent.appendChild(mountPoint);

        bindNavLinks();

        new Vue({
            router: router,
            el: mountPoint,
            template: '<router-view></router-view>',
            mounted: function() {
                console.log('Vue mounted, current route:', this.$route.path);
                updateNav(this.$route.name);
                this.$router.afterEach(function(to) { updateNav(to.name); });
            }
        });
    });
})();
