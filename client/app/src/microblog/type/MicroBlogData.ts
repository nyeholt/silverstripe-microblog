import { MicroPostMap } from "./MicroPostMap";
import { MicroblogMember } from "./MicroBlogMember";


export interface MicroBlogData {
    user?: MicroblogMember,
    users?: {[id: string] : MicroblogMember},
    editingPostId?: string | null,    // post ID
    postsLoading: boolean,
    posts: MicroPostMap
}

