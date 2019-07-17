import { ThunkAction } from 'redux-thunk';
import { GlobalStore } from 'src/type/GlobalStore';
import { BaseAction, ActionType } from 'src/type/Actions';
import { Dispatch, AnyAction } from 'redux';
import wretch from 'wretch';
import { MicroPost } from '../type/MicroPost';
import { MicroblogMember } from '../type/MicroBlogMember';


export function setUser(user: MicroblogMember) : AnyAction {
    return {
        type: ActionType.SET_USER,
        user: user
    }
}

export function createPost(content: string, properties: {[key: string]: any}, postedTo?: {[key: string]: string}): ThunkAction<void, GlobalStore, null, AnyAction> {
    if (!postedTo) {
        postedTo = {
            public: "1"
        }
    }
    return (dispatch: Dispatch, getState: () => GlobalStore) => {
        wretch("/api/v1/microblog/createPost").post({
            content: content,
            properties: properties,
            to: postedTo
        }).json((json) => {
            dispatch(replyToPost(null));
            let postsToLoad = [json.payload];
            if (properties.ParentID) {
                let parentPost = getState().microblog.posts[properties.ParentID];
                if (parentPost) {
                    parentPost = {
                        ...parentPost,
                        NumChildren: parentPost.NumChildren + 1
                    }
                    postsToLoad.push(parentPost);
                }
            }
            return dispatch(loadPostsAction(postsToLoad));
        })
    }
}

export function updatePost(content: string, properties: {[key: string]: any}, postedTo?: {[key: string]: string}): ThunkAction<void, GlobalStore, null, AnyAction> {
    return (dispatch: Dispatch) => {
        wretch("/api/v1/microblog/savePost").post({
            postID: properties.ID,
            postClass: 'MicroPost',
            data: {
                Content: content,
                Title: properties.Title
            }
        }).json((json) => {
            dispatch(editPost(null));
            return dispatch(loadPostsAction([json.payload]));
        })
    }
}

export function loadPosts(filter: string | null = ""): ThunkAction<void, GlobalStore, null, BaseAction> {

    return (dispatch: Dispatch) => {
        wretch("/api/v1/microblog/posts")
            .query({
                filter: filter
            })
            .get()
            .json(json => {
                if (json.payload && json.payload.posts) {
                    dispatch(loadPostsAction(json.payload.posts));
                    dispatch(setUsers(json.payload.users));
                }
            });
    }
}

export function deletePost(postId: string): ThunkAction<void, GlobalStore, null, BaseAction> {
    return (dispatch: Dispatch, getState: () => GlobalStore) => {
        wretch('/api/v1/microblog/deletePost')
            .post({
                postId: postId
            }).json(json => {
                if (json && json.status && json.status == 200) {
                    dispatch({
                        type: ActionType.DELETE_POST,
                        postId: postId
                    });
                    dispatch
                }
            }).catch((err) => {
                console.error("Failed deleting post", err);
            })
    }
}

export function setUsers(users: MicroblogMember[]) {
    return {
        type: ActionType.SET_USERS,
        users: users
    }
}

export function replyToPost(postId: string | null) {
    return {
        type: ActionType.REPLY_TO_POST,
        postId: postId,
    };
}

export function editPost(postId: string | null) {
    return {
        type: ActionType.EDIT_POST,
        postId: postId,
    };
}
export function loadPostsAction(posts: MicroPost[]) {
    return {
        type: ActionType.LOAD_POSTS,
        payload: posts,
    }
}