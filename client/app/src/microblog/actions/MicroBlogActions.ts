import { ThunkAction } from 'redux-thunk';
import { GlobalStore } from 'src/type/GlobalStore';
import { BaseAction, ActionType } from 'src/type/Actions';
import { Dispatch, AnyAction } from 'redux';
import wretch from 'wretch';
import { MicroPost } from '../type/MicroPost';


export function createPost(content: string, properties: {[key: string]: any}, postedTo?: {[key: string]: string}): ThunkAction<void, GlobalStore, null, AnyAction> {
    if (!postedTo) {
        postedTo = {
            public: "1"
        }
    }
    return (dispatch: Dispatch) => {
        wretch("/api/v1/microblog/createPost").post({
            content: content,
            properties: properties,
            to: postedTo
        }).json((json) => {
            return dispatch(loadPostsAction([json.payload]));
        })
    }
}

export function loadPosts(): ThunkAction<void, GlobalStore, null, BaseAction> {
    return (dispatch: Dispatch, getState: () => GlobalStore) => {
        wretch("/api/v1/microblog/posts")
            .get()
            .json(json => {
                if (json.payload) {
                    return dispatch(loadPostsAction(json.payload));
                }
            });
    }
}

export function loadPostsAction(posts: MicroPost[]) {
    return {
        type: ActionType.LOAD_POSTS,
        payload: posts,
    }
}