import axios from "axios";
import { ref, onBeforeUnmount } from "vue";
import { routeAppendParam } from "../Function/global-function";

export const usePaginate = ({
     route,
     callback,
     query
}: any) => {
     const loading = ref(false)
     const routeUrl: any = ref(route);
     const param: any = ref(query)
     const data: any = ref([])
     const information = ref({
          current_page: 1,
          last_page: 1,
          from: 1,
          to: 1,
          total: 1,
          per_page: 10
     })



     const fetchData = () => {
          var queryParam = window.location.search
          var params: any = {}
          if (queryParam) {
               var objQueryParam = new URLSearchParams(queryParam);
               for (const [key, value] of objQueryParam) {
                    params[key] = value;
               }
          }
          params = {
               ...params,
               ...param.value
          }
          if (Object.keys(params).length) {
               params = `?${new URLSearchParams(params).toString()}`
          } else {
               params = ''
          }

          loading.value = true
          axios.get(`${routeUrl.value}${params}`).then((result: any) => {
               const resultData = result.data
               var meta = resultData
               if (resultData.meta) {
                    meta = resultData.meta
               }
               loading.value = false
               data.value = resultData.data
               information.value = {
                    current_page: meta.current_page,
                    last_page: meta.last_page,
                    from: meta.from,
                    to: meta.to,
                    total: meta.total,
                    per_page: meta.per_page
               }
               if (callback) {
                    callback(resultData)
               }
          })
     }

     const updateParam = (props: any) => {
          param.value = props
          fetchData()
     }
     const setRoute = (routeLink: string) => {
          routeUrl.value = routeLink
     }

     const next = () => {
          const page = information.value.current_page + 1
          routeAppendParam({ page })
          fetchData()
     }

     const prev = () => {
          if (information.value.current_page > 1) {
               const page = information.value.current_page - 1
               routeAppendParam({ page })
               fetchData()
          }
     }

     const changeLimit = (limit: any) => {
          limit = Number(limit)
          if (limit >= 5) {
               routeAppendParam({ limit, page: 1 })
               fetchData()
          }
     }

     window.addEventListener("changeUrlParameter", fetchData);

     onBeforeUnmount(() => {
          window.removeEventListener("changeUrlParameter", fetchData);
     })

     fetchData()


     return {
          data,
          information,
          loading,
          fetchData,
          changeLimit,
          next,
          prev,
          setRoute,
          updateParam
     }
}
