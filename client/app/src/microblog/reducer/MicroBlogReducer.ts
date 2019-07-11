import { ReducerMap } from "src/type/ReducerMap";
import { ActionType } from "src/type/Actions";
import { Action, AnyAction } from "redux";
import { MicroBlogData } from "../type/MicroBlogData";
import { MicroPost } from "../type/MicroPost";
import { MicroPostMap } from "../type/MicroPostMap";


const MicroBlogData_default : MicroBlogData = {
    posts: {}
}


const reducers : ReducerMap<MicroBlogData> = {
    [ActionType.STORE_LOAD]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state
        }
    },
    [ActionType.LOAD_POSTS]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        let posts: MicroPost[] = action.payload;

        let postMap: MicroPostMap = {};

        posts.forEach((post) => {
            postMap[post.ID] = post;
        })

        return {
            ...state,
            posts: postMap
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