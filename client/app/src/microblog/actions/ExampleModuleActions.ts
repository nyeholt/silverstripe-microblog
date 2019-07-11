import { ThunkAction } from 'redux-thunk';
import { GlobalStore } from 'src/store/type/GlobalStore';
import { BaseAction, ActionType } from 'src/store/type/Actions';
import { Dispatch } from 'redux';
import wretch from 'wretch';

export function updateData(): ThunkAction<void, GlobalStore, null, BaseAction> {
    return (dispatch: Dispatch, getState: () => GlobalStore) => {
        wretch("https://reqres.in/api/users/2")
            .get()
            .json(json => {
                if (json.data && json.data.first_name) {
                    dispatch({
                        type: ActionType.SET_USERNAME,
                        payload: json.data.first_name
                    });
                }
            });
    }
}