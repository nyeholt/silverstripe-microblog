import { Dispatch } from "redux";

interface RemoteDataSource {
    id: string
    lastUpdate?: number
    frequency: number
    callback?: (dispatch: Dispatch) => void
}

export default RemoteDataSource;