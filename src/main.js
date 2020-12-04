/*
 * @Descripttion:
 * @version:
 * @Author: 刘威
 * @Date: 2020-12-02 16:38:02
 * @LastEditors: 刘威
 * @LastEditTime: 2020-12-03 16:17:40
 */
// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from "vue";
import App from "./App";
import router from "./router";
import "./plugins/Antd";

Vue.config.productionTip = false;

/* eslint-disable no-new */
new Vue({
  el: "#app",
  router,

  components: { App },
  template: "<App/>"
});
