<!--
 * @Descripttion: 
 * @version: 
 * @Author: 刘威
 * @Date: 2020-12-04 14:54:15
 * @LastEditors: 刘威
 * @LastEditTime: 2020-12-04 15:47:04
 -->
<template>
  <div>
    <a-input
      style="width:90%"
      class="row__first"
      ref="userNameInput"
      v-model="StarRating"
      placeholder="请输入后过滤"
    >
      <a-icon slot="prefix" type="user" />
      <a-tooltip slot="suffix" title="可以对英雄名，羁绊，属性，技能进行搜索">
        <a-icon type="info-circle" style="color: rgba(0,0,0,.45)" />
      </a-tooltip>
    </a-input>

    <a-card class="row__first">
      <a-row :gutter="[24,16]">
        <draggable :list="data" @start="start" @end="end" @remove="remove">
          <a-col
            :span="8"
            :lg="12"
            :xs="24"
            :xl="12"
            :xxl="8"
            v-for="(val,index) in data.filter(e => e.title.indexOf(this.StarRating) > -1 
              || e.displayName.indexOf(this.StarRating) > -1
              || e.jobs.indexOf(this.StarRating)>-1
              || e.races.indexOf(this.StarRating)>-1
              || e.skillName.indexOf(this.StarRating)>-1
              )"
            :key="index"
          >
            <a-card :title="val.title">
              <p>
                <a-popover placement="top">
                  <template slot="content">
                    <p>
                      技能：
                      <a-avatar :src="val.skillImage" />
                      {{val.skillName}}({{val.skillType}})({{val.lifeData}})
                    </p>
                    <p style="max-width:350px">技能说明：{{val.skillDetail}}</p>
                    <p>特质：{{val.races}}</p>
                  </template>
                  <template slot="title">
                    <span>职业：{{val.jobs}}</span>
                  </template>
                  <a-avatar class="avatar" :size="52" :src="val._avatar" />
                </a-popover>
              </p>
              <p>{{val.displayName}}</p>
            </a-card>
          </a-col>
        </draggable>
      </a-row>
    </a-card>
  </div>
</template>

<script>
import $__Hero__information from './__Hero__information.json'
import draggable from 'vuedraggable'
export default {
  components: {
    draggable
  },
  props: {
    Herodata: {
      type: Array,
      default: []
    }
  },
  name: "HeroCard",
  data () {
    return {
      StarRating: "",
      src: require("@/assets/__IMG/193.png"),
      data: []
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
  created () {
    this.data = $__Hero__information.data.map(e => {
      e._avatar = require(`@/assets/__IMG/${e.TFTID}.png`)
      return e
    })
    console.log("this.data", this.data)
    this.$emit("update:Herodata", this.data)
  },
  computed: {

  }

}

</script>

<style lang="scss" scope>
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