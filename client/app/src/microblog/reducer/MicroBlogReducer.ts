import { ReducerMap } from "src/type/ReducerMap";
import { ActionType } from "src/type/Actions";
import { Action, AnyAction } from "redux";
import { MicroBlogData } from "../type/MicroBlogData";
import { MicroPost } from "../type/MicroPost";
import { MicroPostMap } from "../type/MicroPostMap";


const MicroBlogData_default : MicroBlogData = {
    posts: {},
    postsLoading: false
}


const reducers : ReducerMap<MicroBlogData> = {
    [ActionType.STORE_LOAD]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state
        }
    },
    [ActionType.START_POSTS_LOAD]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state,
            postsLoading: true
        }
    },
    [ActionType.LOAD_POSTS]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        let posts: MicroPost[] = action.payload;

        let postMap: MicroPostMap = {};

        for (let i in state.posts) {
            postMap[i] = Object.assign({}, state.posts[i]);
        }

        posts.forEach((post) => {
            postMap[post.ID] = post;
        })

        return {
            ...state,
            postsLoading: false,
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