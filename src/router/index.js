/*
 * @Descripttion:
 * @version:
 * @Author: 刘威
 * @Date: 2020-12-02 16:38:02
 * @LastEditors: 刘威
 * @LastEditTime: 2020-12-03 16:20:19
 */
import Vue from "vue";
import Router from "vue-router";

Vue.use(Router);

export default new Router({
  routes: [
    {
      path: "/",
      name: "HelloWorld",
      component: () => import("@/home/home")
    }
  ]
});
