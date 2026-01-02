// This script is **common** for all panels and environments

const loader = {
    mount(props) {
        const content = document.getElementsByClassName('main-content')[0];
        content.style.display = 'none';
        return Promise.resolve();
    },
    unmount(props) {
        const content = document.getElementsByClassName('main-content')[0];
        content.style.display = 'block';
        return Promise.resolve();
    },
    bootstrap() {
        // todo implement
        return Promise.resolve();
    }
};

Promise.all([
    System.import('single-spa'),
    System.import('single-spa-layout'),
]).then(([singleSpa, singleSpaLayout]) => {
    const {constructApplications, constructLayoutEngine, constructRoutes} = singleSpaLayout;
    const {registerApplication, start} = singleSpa;

    const routes = constructRoutes(document.querySelector('#single-spa-layout'), {loaders: {loader}});
    const applications = constructApplications({
        routes,
        loadApp({name}) {
            return System.import(name);
        },
    });
    const layoutEngine = constructLayoutEngine({routes, applications});
    applications.forEach((app) => {
        registerApplication(app, loader, app.activeWhen[0], app.customProps)
    });

    start({urlRerouteOnly: true});
})
