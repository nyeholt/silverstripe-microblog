import * as React from 'react';
import { distanceInWordsToNow } from 'date-fns';

interface State {
    open: boolean
    notifications: any[]
}
interface Props {
    username: string
    profileLink: string
}


export class HeaderNotifications extends React.Component<Props, State> {

    constructor(props : Props) {
        super(props);

        this.state = {
            open: false,
            notifications: [
                {
                    IsSeen: 0,
                    ID: 1,
                    FromUsername: "From",
                    Title: "Note Title",
                    Created: "2018-10-10 10:10:10",
                    Message: "Message here",
                    IsRead: 0,
                }
            ]
        }
    }

    toggleOpen = (): void => {
        if (this.state.notifications) {
            if (!this.state.open) {
                
            }
            this.setState((prevState) => {
                return {
                    open: !prevState.open
                }
            });
        }
    }

    navigate = (link: string): void => {
        location.assign(link)
    }

    render(): JSX.Element {
        const { notifications, open } = this.state;
        const unseen = notifications.reduce(function (unseen, notification) {
            unseen = (notification.IsSeen == 0) ? unseen + 1 : unseen;
            return unseen;
        }, 0);

        return (
            <div className="Header__NotificationsHolder js-react">
                <button className={open ? "HeaderNotificationsToggle HeaderNotificationsToggle--Active" : "HeaderNotificationsToggle"} title="Notifications" onClick={this.toggleOpen}>
                    <svg className="HeaderNotificationsToggle__Image" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 26">
                        <g stroke="none" strokeWidth="1" fill="none" fillRule="evenodd">
                            <g id="Group-7">
                                <path d="M9.4238,20.125 L18.3478,20.125 C16.2848,15.625 16.1618,9.016 16.1618,9.016 C16.1618,5.295 13.1458,2.278 9.4238,2.278 C5.7018,2.278 2.6858,5.295 2.6858,9.016 C2.6858,9.016 2.5628,15.625 0.4998,20.125 L9.4238,20.125 Z"
                                    id="Stroke-1" stroke="#231F20" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"></path>
                                <path d="M12.5,22.7363 C12.5,24.4353 11.123,25.8123 9.424,25.8123 C7.725,25.8123 6.348,24.4353 6.348,22.7363 L12.5,22.7363 Z"
                                    id="Fill-3" fill="#231F20"></path>
                                <path d="M9.4238,2.2778 L9.4238,0.4998" id="Stroke-5" stroke="#231F20" strokeWidth="3" strokeLinecap="round"
                                    strokeLinejoin="round"></path>
                            </g>
                        </g>
                    </svg>
                    {unseen && <span className="HeaderNotificationsToggle__UnreadIndicator"></span>}
                </button>
                {open &&
                    <div className="HeaderNotificationsMenu js-HeaderNotificationsMenu HeaderNotificationsMenu--Active">
                        <ul className="HeaderNotificationsMenu__List">
                            {notifications.map((notification) => {
                                let link : any = null
                                if (notification.ContextValue) {
                                    const context = JSON.parse(notification.ContextValue)
                                    if (context.Link) {
                                        link = context.Link
                                    }
                                }

                                return (<li className="HeaderNotificationsMenu__ListItem" key={notification.ID}>
                                    <div className="HeaderNotification">
                                        <img className="HeaderNotification__AvatarImage" src="/themes/mts-intranet/app/assets/images/icon-profile.svg" alt="Jimmy Smitsarooniekopilaghjfdghjfdghjfd" />
                                        <div className="HeaderNotification__ContentHolder" onClick={() => this.navigate(link + '?IsRead=' + notification.ID)}>
                                            <p className="HeaderNotification__DateTime">
                                                {(notification.IsRead == 0) && <span className="HeaderNotification__UnreadIndicator"></span>}
                                                {distanceInWordsToNow(new Date(notification.Created), { addSuffix: true })}
                                            </p>
                                            <h3 className="HeaderNotification__EventText">{notification.FromUsername} {notification.Title}</h3>
                                            {link ? <a className="HeaderNotification__NotificationText" href={link + '?IsRead=' + notification.ID}>{notification.Message}</a>
                                                : <span className="HeaderNotification__NotificationText">{notification.Message}</span>}
                                        </div>
                                    </div>
                                </li>
                                )

                            })}
                        </ul>
                        {/* <a className="HeaderNotificationsButton" href="#view">View all notifications</a> */}
                    </div>
                }
            </div>
        )
    }
}

