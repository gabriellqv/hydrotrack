module.exports = {
  extends: ["@commitlint/config-conventional"],
  rules: {
    "type-enum": [
      2,
      "always",
      [
        "feat", // Nova funcionalidade
        "fix", // Correção de bug
        "docs", // Alteração de documentação
        "style", // Formatação (sem mudança de lógica)
        "refactor", // Refatoração (sem mudança de comportamento)
        "test", // Adição ou correção de testes
        "chore", // Tarefas de manutenção (deps, config)
        "ci", // Mudanças no CI/CD
        "perf", // Melhoria de performance
      ],
    ],
  },
};
