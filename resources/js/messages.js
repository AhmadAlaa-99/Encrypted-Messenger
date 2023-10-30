import { createApp } from "vue";
import Messenger from "./components/messages/Messenger.vue";
import ChatList from "./components/messages/ChatList.vue";
import Echo from "laravel-echo";

window.Pusher = require("pusher-js"); //object pusher by echo
//subscription tothe basic component (chatApp)
const chatApp = createApp({
    //preparing the connection with pusher
    data() {
        return {
            conversations: [],
            conversation: null,
            messages: [],
            userId: userId,
            csrfToken: csrf_token,
            laravelEcho: null, //--> can use in anywhere -> getpart from data
            users: [],
            chatChannel: null,
            alertAudio: new Audio(
                "/assets/mixkit-correct-answer-tone-2870.wav"
            ),
        };
    },
    //س نستمع الى القناة بمجرد ما التطبيق ينزل الى الدوم
    //we listen to the channel as soon as possible mount on component
    mounted() {
        //execute when component down on dom start exec
        this.alertAudio.addEventListener("ended", () => {
            this.alertAudio.currentTime = 0;
        });

        this.laravelEcho = new Echo({
            // in date registered
            //from .env
            broadcaster: "pusher",
            key: process.env.MIX_PUSHER_APP_KEY,
            cluster: process.env.MIX_PUSHER_APP_CLUSTER,
            forceTLS: true,
        });
        // in laravelecho we use join to exec subsc on presence channel
        //every type channel use different to subsc : (join - listen)
        //join(just name without precence added auto)
        //listen to event
        this.laravelEcho
            .join(`Messenger.${this.userId}`) //subscribe
            // in laravel echo any event add after .listen( default he is namespace App.Event.nameevent
            // to solve this : .new-message : withot namspace (just in echo)
            .listen(".new-message", (data) => {
                //listen
                //add message to others messages
                // (data in comonent chatContent but cant move messages --> move messages to component basic messages[] and in chatContent read messages from $root)
                let exists = false; //if here conversation for this message
                for (let i in this.conversations) {
                    let conversation = this.conversations[i];
                    if (conversation.id == data.message.conversation_id) {
                        //   now open this conversation
                        if (!conversation.hasOwnProperty("new_messages")) {
                            conversation.new_messages = 0;
                        }
                        conversation.new_messages++;
                        conversation.last_message = data.message;
                        exists = true;
                        this.conversations.splice(i, 1);
                        this.conversations.unshift(conversation);

                        //to scroll automatic end caht added alwaws in footer update.....
                        if (
                            this.conversation &&
                            this.conversation.id == conversation.id
                        ) {
                            this.messages.push(data.message);
                            let container =
                                document.querySelector("#chat-body");
                            container.scrollTop = container.scrollHeight;
                        }
                        break;
                    }
                }
                if (!exists) {
                    //new conversation
                    fetch(`/api/conversations/${data.message.conversation_id}`)
                        .then((response) => response.json())
                        .then((json) => {
                            //unshift :show in first
                            //push : show in last
                            this.conversations.unshift(json);
                        });
                }
                this.alertAudio.play(); //notification sound
            });
        //public-precense chaannel just for online - offline.
        //joining :user input intro channel
        this.chatChannel = this.laravelEcho
            .join("Chat") //subscribe
            //مجرد ما تعمل جوين على القناة يرجعلي اوبجيكت من هذه القناة
            .joining((user) => {
                //listen
                //data user from route channel where return datauser and not t or f
                //user input to chaneel (isOnlone) - paramete user from route channel return user
                //required active enable client events in pusher
                //important : 49:00 14
                for (let i in this.conversations) {
                    let conversation = this.conversations[i];
                    if (conversation.participants[0].id == user.id) {
                        this.conversations[i].participants[0].isOnline = true;
                        return;
                    }
                }
            })
            .leaving((user) => {
                //user output from chaneel (isOfline)
                for (let i in this.conversations) {
                    let conversation = this.conversations[i];
                    if (conversation.participants[0].id == user.id) {
                        this.conversations[i].participants[0].isOnline = false;
                        return;
                    }
                }
            })
            //here:all users in channels.
            //whispar(just in channel presense not in private) : Client - Event : sen event from vlient to other without backend server just js->pusher->client
            //this event when write (chatfooter - textarea)
            //do same channel
            // whispar :يجب ان تكون على مستوى ايفينت ليستسنر لذلك خزنا الايفينت ضمن متحول شات
            .listenForWhisper("typing", (e) => {
                let user = this.findUser(e.id, e.conversation_id);
                if (user) {
                    user.isTyping = true;
                }
            })
            //e : اذا اليوزر بلش يكتب ببعت ايفينت انو بدأ يكتب
            // e : الايفينت رح يستقبلها ويكون فيها داتا لي عم يكتب
            //بعمل ايفينت مجرد ما يبدأ اليوزر في الكتابة اي عند التيكست اريا

            .listenForWhisper("stopped-typing", (e) => {
                let user = this.findUser(e.id, e.conversation_id);
                if (user) {
                    user.isTyping = false;
                }
            });
    },
    methods: {
        moment(time) {
            return moment(time);
        },
        isOnline(user) {
            for (let i in this.users) {
                if (this.users[i].id == user.id) {
                    return this.users[i].isOnline;
                }
            }
            return false;
        },
        findUser(id, conversation_id) {
            for (let i in this.conversations) {
                let conversation = this.conversations[i];
                if (
                    conversation.id === conversation_id &&
                    conversation.participants[0].id == id
                ) {
                    return this.conversations[i].participants[0];
                }
            }
        },
        markAsRead(conversation = null) {
            if (conversation == null) {
                conversation = this.conversation;
            }
            fetch(`/api/conversations/${conversation.id}/read`, {
                method: "PUT",
                mode: "cors",
                headers: {
                    "Content-Type": "application/json", //type data to server
                    Accept: "application/json", //from server
                    // 'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: JSON.stringify({
                    _token: this.$root.csrfToken, //csrf token from root exist variable
                }),
            })
                .then((response) => response.json())
                .then((json) => {
                    conversation.new_messages = 0; //hode circle num unreadmessages when press on conv or newmessage and intro conv
                });
        },
        deleteMessage(
            message,
            target //message deleted
        ) {
            fetch(`/api/messages/${message.id}`, {
                method: "DELETE",
                mode: "cors",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    // 'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: JSON.stringify({
                    target: target,
                    _token: this.$root.csrfToken,
                }),
            })
                .then((response) => response.json())
                .then((json) => {
                    // let idx = this.messages.indexOf(message);
                    //indexOf(variblae) : search in array  and return index
                    // this.messages.splice(idx, 1);
                    //splice : delete from array start from index and 1 just
                    ////////or just replace body message
                    message.body = "Message deleted..";
                });
        },
    },
});
chatApp.component("ChatList", ChatList);
chatApp.component("Messenger", Messenger);
chatApp.mount("#chat-app");
