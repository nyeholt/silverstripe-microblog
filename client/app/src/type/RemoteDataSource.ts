import { Dispatch } from "redux";

interface RemoteDataSource {
    lastUpdate?: number
    frequency: number
    callback?: (dispatch: Dispatch) => void
}

export default RemoteDataSource;