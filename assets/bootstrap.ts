import { startStimulusApp, registerControllers } from "vite-plugin-symfony/dist/stimulus/helpers/index";

const app = startStimulusApp();
// @ts-ignore
registerControllers(app, import.meta.glob<StimulusControllerInfosImport>(
  "./controllers/*_controller.ts",
  {
    query: "?stimulus",
    /**
     * always true, the `lazy` behavior is managed internally with
     * import.meta.stimulusFetch (see reference)
     */
    eager: true,
  },
),);
