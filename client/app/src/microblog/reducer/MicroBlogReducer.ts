import { ReducerMap } from "src/type/ReducerMap";
import { ActionType } from "src/type/Actions";
import { Action, AnyAction } from "redux";
import { MicroBlogData } from "../type/MicroBlogData";
import { MicroPost } from "../type/MicroPost";
import { MicroPostMap } from "../type/MicroPostMap";
import { MicroblogMember } from "../type/MicroBlogMember";


const MicroBlogData_default : MicroBlogData = {
    posts: {},
    users: {},
    postsLoading: false,
    savingPost: false,
    filterCount: {},
}

const reducers : ReducerMap<MicroBlogData> = {
    [ActionType.STORE_LOAD]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state
        }
    },
    [ActionType.SET_USERS]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        const userList = action.users;
        const userMap = Object.assign({}, state.users);

        if (userList && userList.length > 0) {
            userList.forEach((user: MicroblogMember) => {
                userMap[user.ID] = user;
            })
        }
        if (userMap) {
            return {
                ...state,
                users: userMap
            }
        }
        return state;
    },
    [ActionType.SET_USER]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        const user = action.user;
        if (user) {
            return {
                ...state,
                user: user
            }
        }
        return state;
    },
    [ActionType.EDIT_POST]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state,
            editingPostId: action.postId
        }
    },
    [ActionType.UPDATING_POST]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state,
            savingPost: action.value
        }
    },
    [ActionType.REPLY_TO_POST]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state,
            replyToPostId: action.postId
        }
    },
    [ActionType.DELETE_POST]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        let postMap: MicroPostMap = {};
        for (let i in state.posts) {
            if (i == action.postId) {
                continue;
            }
            postMap[i] = Object.assign({}, state.posts[i]);
        }

        return {
            ...state,
            posts: postMap
        }
    },
    [ActionType.START_POSTS_LOAD]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        return {
            ...state,
            postsLoading: true
        }
    },
    [ActionType.FILTER_COUNT]: (state: MicroBlogData, action: AnyAction) : MicroBlogData => {
        let filterCount = {
            ...state.filterCount,
            [action.filter]: action.total,
        }
        return {
            ...state,
            filterCount: filterCount
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