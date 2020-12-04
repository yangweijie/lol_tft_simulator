module.exports = {
  root: true,
  // 解析器
  parser: 'babel-eslint',
  parserOptions: {
  //设置"script"（默认）或"module"如果你的代码是在ECMAScript中的模块。
    sourceType: 'module',
    parser: 'babel-eslint',
    ecmaFeatures: {
      legacyDecorators: true,
    },
  },
  // 脚本会在哪种环境下运行, 每个环境带来了一组特定的预定义的全局变量
  env: {
    browser: true,
  },
	// 可以理解成代码的标准 可以通过字符串或者一个数组来扩展规则
  extends: ['airbnb', 'prettier'],
  plugins: [
    'html'
  ],
  // 检查规则
  rules: {
    'arrow-parens': 0,
    'generator-star-spacing': 0,
    'no-debugger': process.env.NODE_ENV === 'production' ? 2 : 0,
    "no-unused-vars": [2, { 
      // 允许声明未使用变量
      "vars": "local",
      // 参数不检查
      "args": "none" 
    }],
    // 关闭语句强制分号结尾
    "semi": [0],
    //空行最多不能超过100行
    "no-multiple-empty-lines": [0, {"max": 50}],
    //关闭禁止混用tab和空格
    "no-mixed-spaces-and-tabs": [0],
  }
}