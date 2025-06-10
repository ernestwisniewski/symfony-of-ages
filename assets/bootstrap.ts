import {startStimulusApp, registerControllers} from "vite-plugin-symfony/dist/stimulus/helpers/index";

const app = startStimulusApp();
registerControllers(
    app,
    // @ts-ignore
    import.meta.glob('./controllers/*_controller.ts', {
        query: "?stimulus",
        /**
         * always true, the `lazy` behavior is managed internally with
         * import.meta.stimulusFetch (see reference)
         */
        eager: true,
    }) as any
)
