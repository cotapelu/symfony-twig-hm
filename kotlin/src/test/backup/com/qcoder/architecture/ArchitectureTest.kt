package com.qcoder.architecture

import com.tngtech.archunit.junit.AnalyzeClasses
import com.tngtech.archunit.junit.ArchTest
import com.tngtech.archunit.lang.ArchRule

@AnalyzeClasses(packages = ["app"])
class ArchitectureTest {
  @ArchTest
  val `no cross-module dependencies`: ArchRule =
    com.tngtech.archunit.library.dependencies.SlicesRuleDefinition
      .slices().matching("app.(modules|shared).(*)..")
      .should().notDependOnEachOther()
      .allowEmptyShould(true)
}
