
import * as moment from 'moment';

declare global {
    interface Window {
        moment: typeof moment;
    }
}
window.moment = moment;