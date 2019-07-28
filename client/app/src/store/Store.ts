
import { createStore, combineReducers, Reducer, compose, applyMiddleware } from 'redux';
import thunk from 'redux-thunk';

// Add your various reducers in here. 

import { GlobalStore } from 'src/type/GlobalStore';
import { ActionType } from 'src/type/Actions';
import microBlogReducer from 'src/microblog/reducer/MicroBlogReducer';
import RemoteSourceDataManager from './RemoteSourceDataManager';

const combinedReducers: Reducer<GlobalStore> = combineReducers<GlobalStore>({
    microblog: microBlogReducer
});

// binds in capabilities for the redux extensions for 
const composeEnhancers = (window as any).__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

const store = createStore(
    combinedReducers,
    composeEnhancers(
        applyMiddleware(thunk)
    )
);

store.dispatch({
    type: ActionType.STORE_LOAD
});

// add store to window for debugging
if (process.env.NODE_ENV !== 'production') {
    (window as any).store = store;
}

RemoteSourceDataManager.setStore(store);

export default store;