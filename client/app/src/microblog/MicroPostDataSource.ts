
import { Dispatch } from 'redux';

import wretch from 'wretch';
import { loadPostsAction } from './actions/MicroBlogActions';
import { ActionType } from 'src/type/Actions';

export default (filters: string) => {
    return {
        id: filters,
        frequency: 23,
        callback: function (dispatch: Dispatch) {
            let w = wretch('/api/v1/microblog/posts');
            if (filters) {
                w = w.query({filters: filters});
            }
            dispatch({
                type: ActionType.START_POSTS_LOAD,
            });
            
            w.get().json((data: any) => {
                if (data && data.status == 200 && data.payload.posts) {
                    dispatch(loadPostsAction(data.payload.posts));
                }
            });
        }
    }
}
