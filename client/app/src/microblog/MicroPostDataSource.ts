
import { Dispatch } from 'redux';

import wretch from 'wretch';
import { loadPostsAction } from './actions/MicroBlogActions';

export default {
    frequency: 5,
    callback: function (dispatch: Dispatch) {

        wretch('/api/v1/microblog/posts').get().json((data: any) => {
            if (data && data.status == 200 && data.payload) {
                dispatch(loadPostsAction(data.payload));
            }
        });
    }
}
