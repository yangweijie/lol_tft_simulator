<!--
 * @Descripttion: 
 * @version: 
 * @Author: 刘威
 * @Date: 2020-12-03 16:19:43
 * @LastEditors: 刘威
 * @LastEditTime: 2020-12-04 15:44:49
 -->

<template>
  <div>
    <a-page-header
      style="border: 1px solid rgb(235, 237, 240)"
      title="首页"
      sub-title="云顶之弈对战模拟器"
      @back="() => null"
    />

    <a-row :gutter="16">
      <draggable :group="[data1,data2]" @start="start" @end="end" @remove="remove">
        <a-col :span="9">
          <HeroCard :Herodata.sync="data1"></HeroCard>
        </a-col>
        <a-col :span="6" :gutter="[24,16]" style="margin:0 auto">
          <div style="margin:0 auto;width:90%">
            <div class="w-comb" v-for="i in 4" :key="i"></div>
            <div class="wrap"></div>
          </div>
        </a-col>
        <a-col :span="9">
          <HeroCard :Herodata.sync="data2"></HeroCard>
        </a-col>
      </draggable>
    </a-row>

    <a-back-top>
      Scroll down to see the bottom-right
      <strong style="color: rgba(64, 64, 64, 0.6)">gray</strong>
      button.
    </a-back-top>
  </div>
</template>
<script>

import HeroCard from "./HeroCard"
import draggable from 'vuedraggable'
export default {
  components: {
    HeroCard,
    draggable

  },
  data () {
    return {
      myArray: [],
      data1: [],
      data2: []
    }
  },
  methods: {
    remove (e) {
      console.log("remove", e)

    },
    start (e) {
      console.log("start", e)

    },
    end (e) {
      console.log("end", e)
    },
  },



}
</script>

 <style lang="scss"  scoped >
.wrap {
  margin: 100px;
  margin-left: 300px;
  width: 505px;
}

.warp__WarZone {
  padding: 10px 50px;
}
.w-comb {
  float: left;
  margin-left: 10px;
  width: 84px;
  height: 84px;
  background: #e4e4e4;
  clip-path: polygon(0 25%, 50% 0, 100% 25%, 100% 75%, 50% 100%, 0 75%);
}
.ant-col {
  text-align: center;
}

.row__first {
  margin: 15px 25px;
  img {
    box-shadow: #0d0f15 0 14px 10px;
    margin: 0 auto;
  }
}

.ant-row .ant-col {
  color: #7e807d;
  img {
    &:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
  }
  .avatar {
    &:hover {
      position: relative;
      z-index: 1;

      transform: scale(1.1);
      cursor: grab;
    }
  }
}
.row__first .card__grid {
  width: 50px;
  height: 50px;
  border: none;
}
</style>