import { ReducerMap } from "src/store/type/ReducerMap";
import { ActionType } from "src/store/type/Actions";
import { Action } from "redux";
import { MicroBlogData } from "../type/MicroBlogData";


const MicroBlogData_default : MicroBlogData = {
    posts: []
}

const reducers : ReducerMap<MicroBlogData> = {
    [ActionType.STORE_LOAD]: (state: MicroBlogData, action: Action) : MicroBlogData => {
        return {
            ...state
        }
    }
}

const microBlogReducer = (state: MicroBlogData = MicroBlogData_default, action: Action) => {
    if (reducers[action.type]) {
        return reducers[action.type].call(this, state, action);
    }
    return state;
}

export default microBlogReducer;