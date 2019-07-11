import { Store } from "redux";
import RemoteDataSource from "src/type/RemoteDataSource";


namespace RemoteSourceDataManager {

    const CHECK_TIME = 5;

    let store : Store;

    let updateTimer : any;

    let dataSources: RemoteDataSource[] = [];

    export function registerDataSource(source: RemoteDataSource) {
        if (store) {
            loadSource(source);
        }
        dataSources.push(source);
    }

    export function setStore(loadedStore: Store) {
        store = loadedStore;
        doUpdate();
    }

    export function stopUpdates() {
        clearTimeout(updateTimer);
    }

    function doUpdate() {
        const now = (new Date()).getTime();

        for (let i = 0, c = dataSources.length; i < c; i++) {
            const source = dataSources[i];
            const nextUpdate = source.lastUpdate ? source.lastUpdate + (source.frequency*1000) : 0;

            if (source.lastUpdate === undefined || nextUpdate < now) {
                loadSource(source);
            }
        }

        updateTimer = setTimeout(doUpdate, CHECK_TIME * 1000);
    }

    function loadSource(source: RemoteDataSource) {
        source.lastUpdate = (new Date()).getTime();
        if (source.callback) {
            source.callback(store.dispatch);
        }
    }
}

export default RemoteSourceDataManager;
