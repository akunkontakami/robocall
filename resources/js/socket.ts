import { reactive } from "vue";
import { io } from "socket.io-client";
import { usePage, router } from "@inertiajs/vue3";
const { VITE_SOCKET_URL, VITE_SOCKET_TOKEN } = import.meta.env;
const page = usePage()

export const state = reactive({
     connected: false,
     connectionId: "",
});

export const socket = io(VITE_SOCKET_URL,
     {
          transports: ['websocket'],
          upgrade: false,
          auth: {
               token: VITE_SOCKET_TOKEN
          },
          extraHeaders: {

          }
     });

socket.on("connect", () => {
     console.log('connected')
     state.connected = true;
     setTimeout(() => {
          const user = page.props.auth.user;
          if (user) {
               state.connectionId = user.id
               socket.emit('join-broadcast', user.id)
          }
     }, 300)
});

socket.on("disconnect", () => {
     console.log('dc')
     state.connected = false;
});

socket.on('BROADCAST', (properties: any) => {
     handleBroadcastEvent(properties)
})


export const joinConnectionBroadcast = () => {
     var user = page.props.auth.user
     if (user) {
          state.connectionId = user.id?.toString() ?? ""
          socket.emit('join-broadcast', user.id)
     }
}

export const leaveConnectionBroadcast = () => {
     var user = page.props.auth.user
     if (user) {
          state.connectionId = user.id?.toString() ?? ""
          socket.emit('leave-broadcast', user.id)
     }
     socket.disconnect();
}

const handleBroadcastEvent = (properties: any) => {
     const { channel, data } = properties

     switch (channel) {
          case 'force_logout':
               /**
                * Handle force logout single login feature
                */
               if (socket.connected) {
                    socket.disconnect()
               }
               router.get(route("auth.logout"),{
                    force : true
               });
               break;

          default:
               break;
     }
}
