
import LocalConfig from './local.config';

const ProjectConfig = {
    "api_endpoint": "http://travel-with-me.symlocal/api/v1",
    "mapbox_token": "please update this",
    "active_save": false,
}

const AppConfig = Object.assign(ProjectConfig, LocalConfig);

export default AppConfig;