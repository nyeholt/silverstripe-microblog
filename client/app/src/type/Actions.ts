import { Action } from "redux";

export const ActionType = {
    STORE_LOAD: "STORE_LOAD",

    SET_USER: "SET_USER",
    SET_USERS: "SET_USERS",
    // from example module, please delete!
    START_POSTS_LOAD: "START_POSTS_LOAD",
    LOAD_POSTS: "LOAD_POSTS",
}

export interface BaseAction extends Action {
    payload: any
}